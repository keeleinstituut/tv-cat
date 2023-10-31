import React, {useContext, useEffect, useRef, useState} from 'react'
import {Select} from '../../common/Select'
import {TabGlossaryContext} from './TabGlossaryContext'
import IconClose from "../../icons/IconClose";


export const DatasetSelect = () => {
    const {searchDataset, setSearchDataset, domainsResponse} = useContext(TabGlossaryContext)

    return (
        <>
            <Select
                className="glossary-dataset-select"
                name="glossary-term-dataset"
                label={false}
                placeholder="No dataset"
                showSearchBar
                searchPlaceholder="Find a dataset"
                options={domainsResponse ? domainsResponse : []}
                activeOption={searchDataset}
                checkSpaceToReverse={false}
                onSelect={(option) => {
                    setSearchDataset(option)
                }}
            ></Select>
            <div
                className={`search_dataset_reset_button ${
                    searchDataset
                        ? 'search_dataset_button--visible'
                        : 'search_dataset_button--hidden'
                }`}
                onClick={() => setSearchDataset(undefined)}
            >
                <IconClose/>
            </div>
        </>
    )
}