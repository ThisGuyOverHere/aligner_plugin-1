import React, {Component} from 'react';
import PropTypes from "prop-types";
import equal from "fast-deep-equal";
import ProjectActions from "../../../../Actions/Project.actions";
import SearchControlsComponent from "./SearchControls/SearchControls.component";
import Hotkeys from "react-hot-keys";

class SearchComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
        close: PropTypes.func
    };

    constructor(props) {
        super(props);
        this.state = {
            elements: this.getElements(),
            searchResults: [],
            searchResultsDictionary: {},
            occurrencesList: [],
            featuredSearchResult: null,
            active: false,
            fulltext: ''
        };
    }

    componentDidUpdate(prevProps) {
        if (!equal(this.state.elements, this.getElements(this.props.job.rows))) {
            this.resetSearch();
        }
    }

    componentDidMount() {
        this.searchInput.focus();
    }

    componentWillUnmount() {
        setTimeout(() => {
            ProjectActions.emitSearchResults({
                q: '',
                searchResults: [],
                searchResultsDictionary: {},
                occurrencesList: [],
                featuredSearchResult: 0
            });
        }, 0)
    }

    render() {
        const {active, featuredSearchResult, occurrencesList} = this.state;
        return (
            <div id="search">
                <form onSubmit={this.onPerformSearch}>

                        <input ref={(input) => {
                            this.searchInput = input;
                        }} type="text" value={this.state.fulltext} onChange={this.onSearchChange}/>
                        {(active && occurrencesList.length > 0)  && <span>{featuredSearchResult + 1} / {occurrencesList.length}</span>}
                        {(active && occurrencesList.length === 0 )  && <span> 0 / 0</span>}

                </form>
                <SearchControlsComponent occurrencesList={occurrencesList}
                                         featuredSearchResult={featuredSearchResult}
                                         setFeatured={this.setFeatured}
                                         close={this.props.close}
                />
            </div>
        );
    }

    onPerformSearch = (e) => {
        e.preventDefault();
        this.setFeatured(this.state.featuredSearchResult + 1);
    };

    getElements = () => {
        let elements = [];
        if (this.props.job.rows) {
            this.props.job.rows.map((row, index) => {
                const source = {
                    content: row.source.content_clean ? row.source.content_clean.toLowerCase() : '',
                    type: 'source',
                    id: row.source.id,
                    index: index,
                    hidden: +row.source.hidden
                };
                const target = {
                    content: row.target.content_clean ? row.target.content_clean.toLowerCase() : '',
                    type: 'target',
                    id: row.target.id,
                    index: index,
                    hidden: +row.source.hidden
                };
                elements.push(source, target);
            });
        }
        return elements;
    };
    resetSearch = () => {
        if (this.state.active) {
            setTimeout(() => {
                ProjectActions.emitSearchResults({
                    q: '',
                    searchResults: [],
                    searchResultsDictionary: {},
                    occurrencesList: [],
                    featuredSearchResult: 0
                });
            }, 0)
        }
        this.setState({
            elements: this.getElements(this.props.job.rows),
            active: false,
            searchResults: [],
            searchResultsDictionary: {},
            featuredSearchResult: 0,
            occurrencesList: [],
            fulltext: ''
        });
    };
    onSearchChange = (event) => {
        let fulltext = event.target.value.toLowerCase();
        let searchResultsDictionary = {};
        const elements = JSON.parse(JSON.stringify(this.state.elements));
        let active = false;
        let searchProgressiveIndex = 0;
        let searchResults = [];
        let occurrencesList = [];

        if (fulltext.length > 0) {
            active = true;
            searchResults = elements.filter(function (item) {
                return item.content.indexOf(fulltext) !== -1 && item.hidden !== 1;
            }).map(item => {
                item.occurrences = [];
                let searchStrLen = fulltext.length;
                let startIndex = 0, index;
                while ((index = item.content.indexOf(fulltext, startIndex)) > -1) {
                    item.occurrences.push({matchPosition: index, searchProgressiveIndex: searchProgressiveIndex});
                    occurrencesList.push({index: item.index, id: item.id});
                    searchProgressiveIndex++;
                    startIndex = index + searchStrLen;
                }
                item.occurrences.reverse();
                searchResultsDictionary[item.id] = item;
                return item
            });
            ProjectActions.emitSearchResults({
                q: fulltext,
                searchResults: searchResults,
                searchResultsDictionary: searchResultsDictionary,
                occurrencesList: occurrencesList,
                featuredSearchResult: 0
            });
            this.setState({
                fulltext: event.target.value,
                active: active,
                searchResults: searchResults,
                searchResultsDictionary: searchResultsDictionary,
                occurrencesList: occurrencesList,
                featuredSearchResult: 0
            })
        } else {
            this.resetSearch()
        }


    };

    setFeatured = (value) => {
        if (this.state.occurrencesList.length > 1) {
            let module = this.state.occurrencesList.length;
            value = this.mod(value, module);
        } else {
            value = 0;
        }

        ProjectActions.emitSearchResults({
            q: this.state.fulltext,
            searchResults: this.state.searchResults,
            searchResultsDictionary: this.state.searchResultsDictionary,
            occurrencesList: this.state.occurrencesList,
            featuredSearchResult: value
        });
        this.setState({
            featuredSearchResult: value
        })
    };

    // handling module
    mod = (n, m) => {
        return ((n % m) + m) % m;
    }
}

export default SearchComponent;
